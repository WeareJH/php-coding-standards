# php-coding-standards
Custom PHP CS Rules

## Installation

Add the following to your `composer.json` file:

```
"repositories" : [
    {
        "type": "vcs",
        "url": "git@github.com:WeareJH/php-coding-standards.git"
    }
]
```

Then run:

```
composer require wearejh/php-coding-standards --dev
```

## Usage

Assuming you have phpcs installed:

```
./vendor/bin/phpcs -s /folder/to/code --standard=vendor/wearejh/php-coding-standards/Jh
```