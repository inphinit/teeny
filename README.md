## Install using composer

For create your project use:

```
composer create-project inphinit/tenny <project name>
```

Replace `<project name>` by your project name, for exemple, if want create your project with "blog" name (folder name), use:

```
composer create-project inphinit/tenny blog
```

## Download without composer

If is not using `composer` try direct download from https://github.com/inphinit/tenny/releases

## Execute

Copy for Apache ou Nginx folder and configure Vhost in Apache or execute direct from folder

## API

Methods from `Tenny` class

Method | Description
---|---
`Tenny::path(): string` | Get current path from URL
`Tenny::status([int $code]): int` | Get or set HTTP status
`Tenny::action(mixed $methods, string $path, mixd $callback): void` | Add or remove or update a route
`Tenny::handlerCodes(array $codes, mixd $callback): int` | Detect if SAPI or script change HTTP status
`Tenny::exec([bool $builtin]): bool` | Execute defined route, use `$builtin` for built-in-server for detect if file exists
