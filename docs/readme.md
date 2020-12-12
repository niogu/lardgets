Edit composer.json:

```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/niogu/lardgets"
    }
],
```

```
composer require --dev niogu/lardgets:dev-master
```

```sh
@lardgetsjs

php artisan make:lardget Name
```