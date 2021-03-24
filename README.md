## DualResource (Laravel library)

**DualResource** is a derived class of [Laravel Resource](https://laravel.com/docs/8.x/eloquent-resources).  
By DualResource, in addition what [API Resource](https://laravel.com/docs/8.x/eloquent-resources) can to do, you can map request data to array with same template.   

* Map Request Data To new array
* Map Model To json

## Installation

#### Require with [Composer](https://getcomposer.org/)
```shell script
composer require hamidmp/dualresource
```

## Usage

```php
class Person {
    // firstName, lastName, city, ...
    
    public function cars(){
        return $this->hasMany(Car::class);
    }
}

class Car {
    // person_id, name, model, ...   
}
```

**1. Using _`fname`_ and _`lname`_ instead of _`firstName`_ and _`lastName`_ in client side (API or UI)**
```php
class PersonDualResource extends DualResource  {

    protected function mapFields()
    {
        return [
            'fname'=>'fisrtName',
            'lname'=>'lastName',
        ];
    }

}
```
```php
$data = PersonDualResource::fromRequest($request)->getParameters();
//$request contained: fname, lname
//$data will contain: firstName , lastName 
```
*Note: Method `mapFields` will generate `toArray` method and you don't need to change it.*  

**2. With relationship**
```php
class PersonDualResource extends DualResource  {

    protected function mapFields()
    {
        return [
            'fname'=>'fisrtName',
            'lname'=>'lastName',
            'car_list'=>['cars',CarDualResource::class],
        ];
    }

}
```
```php
$data = PersonDualResource::fromRequest($request)->getParameters();
//$request contained: fname, lname, car_list:[ {name, model},... ]
//$data will contain: firstName , lastName, cars=>[ [name,model],... ] 
```
**3. Using anonymous function for mapping**
```php
class PersonDualResource extends DualResource  {

    protected function mapFields()
    {
        return [
            'fname'=>'fisrtName',
            'lname'=>'lastName',

            'car_list'=>['cars',function($key){
                //for response
                return CarDualResource::collection($this->{$key});

            },function($data){
                //for request
                return CarDualResource::fromRequestData($data);

            }],
        ];
    }

}
```
*Note: You can ignore each function by returning `null` value: `return null;`*  

**4. Using as normal Resource class (for response)**
```php
$person=\App\Models\Person::with('cars')->find(1);
return \App\Http\Resources\PersonDualResource::fromModel($person)->response();
```

## Methods
* Preparing class
```php
//Declaring assign-map between model and data
//return array 
protected function mapFields()
```

* from request data
```php
//Preparing DualResource for mapping data from request
public static function fromRequest(Request $request)

//return array of mapped request data
public function getParameters($extra=[], $defaults=[])

//return array of mapped request data plus other ones
public function getAll($extra=[], $defaults=[])
```

* for response (json)
```php
//return a new resource instance from one model
public static function fromModel(...$parameters)

//return a new anonymous resource collection from Models
public static function fromModels($resource)
```

## License

The DualSource library is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
