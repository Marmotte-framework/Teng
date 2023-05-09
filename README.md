# MdGen

Markdown template engine

## Templates

MdGen can handle 2 file types: `.md.mdgen` and `.html.mdgen`. Firsts are
classic [Markdown](https://wikipedia.org/wiki/Markdown) files, when seconds are HTML files. For both files, you can use
a little script language (describe below) to have dynamic templates.

Please note that in Markdown files, only inline HTML tags are handled. If you use others tags, you may get some errors.

### Variables

When you render your template with the engine, the `render` method can take an array as argument. This array should be
list of `key => value` pairs. Whe doing this, you made values available in template by calling key. If the value is
itself an object or an array. You can access to sub values using a dot notation (`key.subkey.subsubkey`). If the value
is a classic array (indexed by numbers), you can access to the nth value with square bracket notation (`key[n]`). `n`
can of course be replaced by another variable.

To place the value of a variable in your template, use this syntax: `{{ key }}`.

For example, if you call `render` method with this array:

```php
$engine->render('template', [
    'key' => 'value'
]);
```

In the template, `{{ key }}` will be replaced by `value`.

### Conditions

Conditions are essential to choose if a portion of template is shown or not. There is two forms of conditions.

**Positive:**

```mdt
{# key }}
    ...
{{ key #}
```

Tests if variable is set and its value is true or not null.

**Negative:**

```mdt
{! key }}
    ...
{{ key !}
```

Tests if variable is not set or its value is false or null.

For each case, if condition is valid, the template present between the two tag will be shown.
The notation for `key` is the same as for [variables](#variables)

### Loops

When the value of your variable is an array, it can be useful to iterate on it. For that you can use this syntax:

```mdt
{( array: key -> value }}
    ...
{{ array )}
```

You iterate on array `array`, keys are `key` and values are `value`. If you don't need the key, you can iterate only on
values by using this syntax:

```mdt
{( array: value }}
    ...
{{ array )}
```

### Functions

Last but not least: functions. There is 2 ways of using functions, call them directly to get what they return or call
them on variables as filters.

If you want to call them directly, use this syntax:

```mdt
{| function }}
```

Or this one to pass some arguments:

```mdt
{| function(...) }}
```

In other hand, if you want to apply the function as a filter on a variable, use this syntax:

```mdt
{{ key | function }}
```

In this case, the function takes only one argument, the variable. The type of variable must match the type of function
argument.

If you want to use a custom function, you can register it in the engine. You can either register an anonymous function,
or the method of a class.

```php
// Add an anonymous function
$engine->addFunction('nameOfFunction', static fn() => 'Hello World!');

// Add method of a class
$my_class = new MyClass();
$engine->addFunction('nameOfFunction', [$my_class, 'myMethod']);
```
