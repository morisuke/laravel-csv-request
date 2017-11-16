Laravel Csv Request Parser
====

Perform internal data validation and parsing of the CSV file.

## Description
Parsing the request of CSV file received by Laravel, validate the internal data and extract it.  
Currently it is implemented assuming to convert the input of SJIS-win to UTF-8.

## Requirement
"php": ">=7.0"  
"laravel/framework": "5.1.*|5.2.*|5.3.*|5.4.*"

## Usage

Create request class.

```bash
php artisan make:request UserCsvRequest
```

Extend the ```CsvRequest``` class and remove the ```rules``` method.  
Then implement the ```csvRules``` method.  
This method is the validation rule of CSV internal data.

```php
<?php

namespace App\Http\Requests;

use Morisuke\CsvRequest\Http\Requests\CsvRequest;

class UserCsvRequest extends CsvRequest
{
    public function authorize()
    {
        return true;
    }

    public function csvRules()
    {
        return [
            'id'              => 'required|integer',
            'name'            => 'required|max:255',
            'is_public'       => 'required|boolean',
        ];
    }
}
```

Instantiate with the controller and run ```getCsvIterator```.  
```getCsvIterator``` returns a generator, so you can loop it foreach.  
Since the data retrieved for each loop is wrapped in ```Illuminate\Support\Collection```, retrieve and use the internal data with the all method or get method.  
The name of the data matches the definition of ```csvRules```.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserCsvRequest;
use App\User;

class UserController extends Controller
{
    public function uploadCsv(UserCsvRequest $request, User $user)
    {
        foreach ($request->getCsvIterator() as $column)
        {
            $user->where('id', $column->get('id'))
                ->firstOrNew([])
                ->fill($column->all())
                ->save();
        }

        return back()->with(['success' => 'Successful CSV upload.']);
    }

}
```

If validation fails, automatic redirection occurs and errors are stored in the ```$ errors``` variable of the blade template.  
As ```csv_column_number``` stores the wording on what line the error occurred, check the existence and display an error.

```php
@if($errors->has('csv_column_number'))
    @foreach($errors->all() as $error)
    <div class="label label-error">{{ $error }}</div>
    @endforeach
@endif
```

## Install

```bash
composer config repositories.csvrequest vcs https://github.com/morisuke/laravel-csv-request.git
composer require morisuke/laravel-csv-request
```

## Licence

[MIT](https://github.com/tcnksm/tool/blob/master/LICENCE)

## Author

[morisuke](https://github.com/morisuke)

