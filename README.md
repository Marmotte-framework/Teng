# Teng

Template engine

## Templates

Teng can handle 2 file types: `.md.teng` and `.html.teng`. Firsts are
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
$engine->render('template.md.teng', [
    'key' => 'value'
]);
```

In the template, `{{ key }}` will be replaced by `value`.

### Conditions

Conditions are essential to choose if a portion of template is shown or not. There is two forms of conditions.

**Positive:**

```teng
{# key }}
    ...
{{ key #}
```

Tests if variable is set and its value is true or not null.

**Negative:**

```teng
{! key }}
    ...
{{ key !}
```

Tests if variable is not set or its value is false or null.

For each case, if condition is valid, the template present between the two tag will be shown.
The notation for `key` is the same as for [variables](#variables)

### Loops

When the value of your variable is an array, it can be useful to iterate on it. For that you can use this syntax:

```teng
{( array: key -> value }}
    ...
{{ array )}
```

You iterate on array `array`, keys are `key` and values are `value`. If you don't need the key, you can iterate only on
values by using this syntax:

```teng
{( array: value }}
    ...
{{ array )}
```

### Functions

Last but not least: functions. There is 2 ways of using functions, call them directly to get what they return or call
them on variables as filters.

If you want to call them directly, use this syntax:

```teng
{| function }}
```

Or this one to pass some arguments:

```teng
{| function(...) }}
```

In other hand, if you want to apply the function as a filter on a variable, use this syntax:

```teng
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

### Includes

Sometimes you may need to decompose your template in several files, well named includes are here for that. With below
syntax you can include another template in the current one. When you are doing so, the included template share the same
values as the original one. So no need to pass some values explicitly (it's very useful inside loops).

```teng
{> mySecondTemplate }}
```

For example in a loop of user:

```teng
<!-- list_users.html.teng -->
<ul>
    {( users: user }}
    <li>{> show_user.html.teng }}</li>
    {{ users )}
</ul>

<!-- show_user.html.teng -->
<img src="{{ user.image }}"> {{ user.name }}
```

### Base template

Another feature which is very useful: base template. If you need to use the same base for your templates several times,
it can be painful to copy/paste the base each time (and don't talk about the refactoring). For this case you can use
base template. At the beginning of your template you specify another template which be used as a 'template of template'.
Let take an example.

First, let define a base template:

```teng
<!-- base.html.teng -->
<!DOCTYPE html>
<html lang="en">
<head>
    <title>{% title }}My Website{{ title %}</title>
    {% style }}{{ style %}
</head>
<body>
{% body }}
<p>An empty body</p>
{{ body %}
</body>
</html>
```

In this file we use this syntax: `{% block }}{{ block %}` to define content blocks. They can have a default value as for
title and body or not as for style. Then in your second template you just override these blocks to choose what is their
content:

```teng
<!-- template.md.teng -->
{@ base.html.teng }}

{% title }}My template{{ title %}

{% body }}
# My template

Lorem ipsum...
{{ body %}
```

To specify your base template, you use `{@ baseTemplate }}` syntax, then you have just to override the blocks you want.
Please note that content outside the blocks will not be displayed.