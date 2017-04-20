# Predis Adapter for Stash

Because Stash is great but did happen to neglect the usage of Predis in real world applications.

## Installing

Installing Stash can be done through a variety of methods, although Composer is
recommended.


### Composer

Until Stash reaches a stable API with version 1.0 it is recommended that you
review changes before Minor updates, although bug fixes will always be
backwards compatible.


```
{
  "repositories": [
    {
      "type": "git",
      "url": "https://github.com/bandit-talent/bandit-stash-predis-adapter.git"
    }
  ],
  "require": {
    "bandit.io/bandit-stash-predis-adapter": "^1.0.0"
  }
}
```

### Usage

- Create an instance of `\Predis\Client`
- Inject it to `Bandit\Stash\Driver\Predis`
