# Reactive Sockets Abstraction
 
Abstraction over sockets in applications to make code more testable and portable across various reactive frameworks.

[![Build Status](https://travis-ci.com/EcomDev/reactive-socket.svg?branch=master)](https://travis-ci.com/EcomDev/reactive-socket) 
[![Maintainability](https://api.codeclimate.com/v1/badges/c476b63cd70c861a7fac/maintainability)](https://codeclimate.com/github/EcomDev/reactive-socket/maintainability) 
[![Test Coverage](https://api.codeclimate.com/v1/badges/c476b63cd70c861a7fac/test_coverage)](https://codeclimate.com/github/EcomDev/reactive-socket/test_coverage) 

## Why do you need it?
Testing async applications in ReactPHP and similar frameworks can be very cumbersome. Also your code is tightly bound to the framework you base your code on.
This library provide complete abstraction of stream from react and gives a possibility to switch to another framework at any point in time.
Just implement custom `EventEmitter` in your favorite framework and you are done. 

Even more you can combine multiple emitters in multi-process application, e.g. http server using libuv and ipc (socket pairs) using simple stream-select. 

## Installation
```bash
composer require ecomdev/reactive-socket
```

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
