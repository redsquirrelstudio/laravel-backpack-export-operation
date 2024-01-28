# Export Operation for Backpack for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]

[//]: # ([![Total Downloads][ico-downloads]][link-downloads])

Adds a configurable interface that allows your admin users to:

- Export CRUD resources to multiple file formats.
- Decide which columns they would like to export.

and allows you as the developer to:

- Customise each CRUD's export behaviour using the Backpack API you know and love.
- Choose between queued or instant exports.
- Completely customise the operation's behaviour.

[!["Buy Me A Coffee"](https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png)](https://www.buymeacoffee.com/redsquirrelstudio)

If you're looking for a great team of developers to handle some Backpack/Laravel development for you, drop us a line
at [Sprechen][link-sprechen]

![Screenshot of the operation's configuration screen](https://raw.githubusercontent.com/redsquirrelstudio/laravel-backpack-export-operation/dev/assets/screenshot.jpg?raw=true)

Powering the exports in the background is [```maatwebsite/excel```][link-laravel-excel]

## Table of Contents

1. [Installation](#installation)
2. [Usage](#usage)
3. [Disabling User Configuration](#disabling-user-configuration)
4. [Queued Exports](#queued-exports)
5. [Configuration](#configuration)
   1. [File Storage](#file-storage)
   2. [Queues](#queues)
   3. [Changing the Export log Model](#export-log)
   4. [Customising Translations](#translations)
   5. [Customising Views](#views)
6. [Export Completed Event](#export-completed-event)
7. [Restricting Access](#restricting-access)
8. [Credits](#credits)
9. [License](#license)

## Installation

**Environment Requirements**

- PHP extension php_zip
- PHP extension php_xml
- PHP extension php_gd2
- PHP extension php_iconv
- PHP extension php_simplexml
- PHP extension php_xmlreader
- PHP extension php_zlib

**Step 1.**

Require the package with composer:

```bash
composer require redsquirrelstudio/laravel-backpack-export-operation
```

This will also install [```maatwebsite/excel```][link-laravel-excel] if it's not already in your project.

**Step 2. (Optional)**

If you would like to add the option to export PDFs, you should also install dompdf:
```bash
composer remove dompdf/dompdf
```

**Step 3. (Optional)**

The service provider at: ```RedSquirrelStudio\LaravelBackpackExportOperation\Providers\ExportOperationProvider```
will be auto-discovered and registered by default. Although, if you're like me, you can add it to
your ```config/app.php```.

```php
    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */
        //Some other package's service providers...
        RedSquirrelStudio\LaravelBackpackExportOperation\Providers\ExportOperationProvider::class,
    ])->toArray(),
```

**Step 4.**

Publish the config file:

```bash
php artisan vendor:publish --tag=laravel-backpack-export-operation-config
```

This will create a new file at ```config/backpack/operations/export.php``` allowing you
to customise things such as the disk and path exported files should be stored at.

**Step 5.**

Publish and run the migration:

```bash
php artisan vendor:publish --tag=laravel-backpack-export-operation-migrations
```

*Then*

```bash
php artisan migrate
```

## Usage

In your CRUD Controller where you need the export operation.

*Wait for it...*

**Add the export operation:**

```php
class ExampleCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \RedSquirrelStudio\LaravelBackpackExportOperation\ExportOperation;
    //...
```

But wait! There's more!

### Configuring the Export

Configuring the export is exactly the same as how you would configure the list operation.
Simply define which columns you would like to export, here's an example:

```php
    //Probably some more CRUD config...
    
    protected function setupExportOperation()
    {
        CRUD::addColumn([
           'name' => 'id',
           'label' => 'ID',
           'type' => 'number',
        ]);

        CRUD::addColumn([
           'name' => 'name',
           'label' => 'Name',
           'type' => 'text',
        ]);  
    }
    
    //Fetch functions or something...  
```

Pretty much all columns that are available for the list operation will work
fine in an export. This also means you can define your own columns in the exact same way as you would
with list columns.

For a list of available column types, [see Backpack for Laravel's Documentation][link-backpack-list]


## Disabling User Configuration

Sometimes, you may not want the user to be able to choose which columns are included in their export,
In these cases, you can disable the user configuration step.

To enable this behaviour, add this one line
of code to the ```setupExportOperation()``` function:

```php
    //...
    protected function setupExportOperation()
    {
        $this->disableUserConfiguration();
    //...
```

## Queued Exports

In most situations, it is going to be better for the user
if your exports are processed in the background rather than making them
wait for the export to finish processing.

Therefore, you have the option to queue your exports by adding this line
of code to the ```setupExportOperation()``` function:

```php
    //...
    protected function setupExportOperation()
    {
        $this->queueExport();
    //...
```

When this option is enabled, you will need to handle what happens when the export finishes or the user will not
receive their export. 
To do this, you should handle the ```ExportCompleteEvent``` using an event listener. This event contains the export log
which you can get the file path from to send to the user via email, notification etc.

[Read about the export complete event here](#export-completed-event)

[Learn how to handle events on Laravel's official Docs][link-laravel-event-docs]

Of course, for this to work, you will need to set up a queue for
your application to dispatch jobs to, to do that, [follow Laravel's
official docs][link-laravel-queue-docs].

When this setting has been enabled, the user will be redirected to the current CRUD's list view.
An alert will appear in the top right which has a default message.

If you would like to change this message, add the following line to the ```setupExportOperation()``` function: 
```php
    //...
    protected function setupExportOperation()
    {
        $this->setQueueMessage("Your Message about the export being queued.");
    //...
```

## Configuration

### File Storage

By default, export files will be stored in your default disk
at the path /exports. but this can be altered either by changing the following
env variables:

```dotenv
FILESYSTEM_DISK="s3"
BACKPACK_EXPORT_FILE_PATH="/2024/application-name/exports"
```

Or by directly changing the options within ```config/backpack/operations/export.php```.

```php
    //...
    //Filesystem disk to store export files
    'disk' => "s3",
    
    //Path to store export files
    'path' => "/2024/application-name/exports",
    //...
```

### Queues

You can also change the queue that queued exports are dispatched to
 by changing the following env variables:

```dotenv
QUEUE_CONNECTION="export-queue"
```

or changing the value directly within ```config/backpack/operations/export.php```.

```php
    //...
    //Queue to dispatch export jobs to
    'queue' => 'export-queue',
    //...
```

### Export Log

In very rare cases, you may wish to also change the model
that is used to log exports, I can't think of a reason why, but I'm
sure someone will come up with one.

If you do, make sure to update the migration, and specify your own
model at ```config/backpack/operations/export.php```.

```php
//...
return [
    'export_log_model' => ExportLog::class,
    //...
```

### Translations

You can update the operation translations if required. To do this run:

```bash
php artisan vendor:publish --tag=laravel-backpack-export-operation-translations
```

this will publish the operation lang files to ```resources/lang/vendor/backpack/export-operation```
The files stored in this directory take priority over the package's default lang files.

### Views

You can update the operation views if required. To do this run:

```bash
php artisan vendor:publish --tag=laravel-backpack-export-operation-views
```

this will publish the operation blade files to ```resources/views/vendor/backpack/export-operation```
The files stored in this directory take priority over the package's default views.

## Export Completed Event

This package fires an event when an export has been completed.
The event payload contains the export log so that you can send an email, notification or whatever else with a download
url for the file.

##### Event Class:

```php
RedSquirrelStudio\LaravelBackpackExportOperation\Events\ExportCompleteEvent::class
```

##### Payload:

```php
[
    //The Completed Export
   'export_log' => RedSquirrelStudio\LaravelBackpackExportOperation\Models\ExportLog::class 
]
```

## Restricting Access

Like most operations in Backpack, you can restrict user access using the following line of code in your CRUD
Controller's setup function:

```php
    public function setup()
    {
        //...
        CRUD::denyAccess('export');
        //...
    }
```

## Credits

- [Lewis Raggett][link-me] and [The Team at Sprechen][link-sprechen]  :: Package Creator
- [Cristian Tabacitu][link-backpack] :: Backpack for Laravel Creator
- [Spartner][link-laravel-excel] :: Laravel Excel Creator
- [DomPDF][link-dompdf] :: DOMPDF Creator

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/redsquirrelstudio/laravel-backpack-export-operation?style=flat-square

[ico-license]: https://img.shields.io/badge/license-dual-blue?style=flat-square

[link-packagist]: https://packagist.org/packages/redsquirrelstudio/laravel-backpack-export-operation

[ico-downloads]: https://img.shields.io/packagist/dt/redsquirrelstudio/laravel-backpack-export-operation.svg?style=flat-square

[link-downloads]: https://packagist.org/packages/redsquirrelstudio/laravel-backpack-export-operation

[link-laravel-excel]: https://laravel-excel.com

[link-carbon]: https://carbon.nesbot.com/docs

[link-laravel-event-docs]: https://laravel.com/docs/10.x/events#defining-listeners

[link-laravel-queue-docs]: https://laravel.com/docs/queues

[link-backpack-list]: https://backpackforlaravel.com/docs/6.x/crud-columns

[link-backpack]: https://github.com/Laravel-Backpack

[link-me]: https://github.com/redsquirrelstudio

[link-sprechen]: https://sprechen.co.uk

[link-dompdf]: https://github.com/dompdf/dompdf
