
# Predis Adapter for Stash
[![Latest Stable Version](https://poser.pugx.org/nilportugues/stash-predis-adapter/v/stable)](https://packagist.org/packages/nilportugues/stash-predis-adapter) 
[![Total Downloads](https://poser.pugx.org/nilportugues/stash-predis-adapter/downloads)](https://packagist.org/packages/nilportugues/stash-predis-adapter) 
[![License](https://poser.pugx.org/nilportugues/stash-predis-adapter/license)](https://packagist.org/packages/nilportugues/stash-predis-adapter) 
[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://paypal.me/nilportugues)

Because [Stash](http://www.stashphp.com/) is great but did happen to neglect the usage of Predis in real world applications.

## Installing

Installing Stash can be done through a variety of methods, although Composer is
recommended.

### Usage

- Create an instance of `\Predis\Client`
- Inject it to `NilPortugues\Stash\Driver\Predis`
