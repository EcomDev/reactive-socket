# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]


## [1.0.1]
### Updated
- Updated readme with better description
- Clarified worker description in `EventEmitterBuilder`
- Marked method `StreamSelectEventEmitter::__invoke` as internal 


## [1.0.0] 
### Added
- `InMemoryStreamBuffer` for doing in memory simulation of sockets 
- `SocketStreamBuffer` for using with real socket stream
- `FakeStreamObserver` and `StreamObserverNotificationState` for testing custom implementation of buffers, streams and event emitters
- `InMemoryStreamBuffer` and `SocketStreamBuffer` provide implementation for `StreamClient`, but hidden behind observer 
- `BufferedStream` that utilizes `StreamBuffer` as an abstraction of stream resource
- `StreamSelectEventEmitter` and `StreamSelectEventEmitter` for simple zero dependency socket server implementations
- `SimpleLoopRunner` for easy testing of event loop in socket applications

[Unreleased]: https://github.com/ecomdev/reactive-socket/compare/1.0.1...HEAD
[1.0.1]: https://github.com/ecomdev/reactive-socket/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/ecomdev/reactive-socket/compare/4b825dc642cb6eb9a060e54bf8d69288fbee4904...1.0.0
