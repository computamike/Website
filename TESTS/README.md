# Unit Testing Notes (2020-08-17)
These are some unit testing notes that I have added to help developers in the future.

## Glossary of terms
Developers have different terms for possibly the same thing - I'll write up the terms I am using here : 

### Dependency Injection
The ability to pass everything that a method or class needs - typically though the class constructor, or through the method itself.  Dependency Injection allows a thing needed to execute, to be replaced with a Test Double

### Test Doubles / Test Mock / Test Stub / Test Fakes
There are subtle difference between Fakes, Mocks and Stubs - these differences are down to whether the Mock / Fake / Stub does any 'work', records whether a method was called, and returns any results.
the article [TestDoubles - Fakes, Mocks and Stubs](https://blog.pragmatists.com/test-doubles-fakes-mocks-and-stubs-1a7491dfa3da) discusses these differences - I'll be using the term Test Double to refer to something that is a test replacement of production code. the Mocking framework added (Mockery) supports creation of all three types of Test Doubles.

---

## Dependancy Injection
CCHits currently does not have a Dependency Injection framework, so it is not possible to inject what I will refer to as Test Doubles into code for testing purposes.  To assist developers, the following libraries have been added to CCHits through Composer.

* Mockery
* Patchwork

### Mockery
Mockery allows the developer to create a [test double](#test-doubles-/-test-mock-/-test-stub-/-test-fakes) of a class that would normally be created in a System Under Test (SUT).  

### Patchwork
Patchwork allows the developer to override a PHP command, and replace it with a custom command

Let's see these 2 concepts working together.
```php


```


# Testing Notes : Docker
If you're not using Docker, then these notes can be ignored.

The following command will rung PHP in your container...
docker exec -it fbc1e200e615bbe9dab93825e00e02bef9307ec10da4e484711fa433f4dada32 /bin/sh -c "cd /var/www/html;./vendor/bin/phpunit"