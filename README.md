# Predis Adapter for Stash

Because [Stash](http://www.stashphp.com/) is great but did happen to neglect the usage of Predis in real world applications.

## Installing

Installing Stash can be done through a variety of methods, although Composer is
recommended.

### Usage

- Create an instance of `\Predis\Client`
- Inject it to `NilPortugues\Stash\Driver\Predis`
