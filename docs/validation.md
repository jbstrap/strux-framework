# Validation

The validation system can be used independently or within Forms.

---

## Table of Contents

- Using the Validator
- Validation Rules Reference
- Custom Validation
- Custom Error Messages

---

## Using the Validator

You can manually use the Validator service in your controllers.

```php
use Strux\Component\Validation\Validator;
use Strux\Component\Validation\Rules\Required;
use Strux\Component\Validation\Rules\Email;

$validator = new Validator($request->all());

$validator->add('email', [
    'required',               // String syntax
    new Email('Invalid!')     // Object syntax with custom message
]);

if ($validator->isValid()) {
    // Proceed...
} else {
    $errors = $validator->getErrors();
}
````

---

## Validation Rules Reference

Rules can be defined as strings (e.g., `'minlength:5'`) or objects (e.g., `new MinLength(5)`).

| Rule         | String Syntax       | Object Syntax         | Description                           |
|--------------|---------------------|-----------------------|---------------------------------------|
| Alpha        | `'alpha'`           | `new Alpha()`         | Only alphabetic characters            |
| Alphanumeric | `'alphanumeric'`    | `new Alphanumeric()`  | Letters and numbers only              |
| Confirmed    | `'confirmed'`       | `new Confirmed()`     | Checks matching `_confirmation` field |
| Date         | `'date'`            | `new Date()`          | Must be a valid date string           |
| Email        | `'email'`           | `new Email()`         | Must be a valid email format          |
| Equal        | `'equal:val'`       | `new Equal('val')`    | Must match the given value exactly    |
| In           | `'in:a,b,c'`        | `new In('a','b')`     | Must be one of the specified values   |
| Integer      | `'integer'`         | `new Integer()`       | Must be a valid integer               |
| MaxLength    | `'maxlength:10'`    | `new MaxLength(10)`   | String length must be <= limit        |
| MinLength    | `'minlength:5'`     | `new MinLength(5)`    | String length must be >= limit        |
| NotIn        | `'notin:a,b'`       | `new Notin('a','b')`  | Must NOT be one of the values         |
| Numeric      | `'numeric'`         | `new Numeric()`       | Must be numeric                       |
| Password     | `'password'`        | `new Password()`      | Complex password validation           |
| Regex        | `'regex:/pattern/'` | `new Regex('/patt/')` | Matches regular expression            |
| Required     | `'required'`        | `new Required()`      | Must not be empty or null             |
| Same         | `'same:other'`      | `new Same('other')`   | Field value must match another        |
| Url          | `'url'`             | `new Url()`           | Must be a valid URL                   |

---

## Custom Validation

You can pass a Closure (Anonymous Function) as a validation rule.

**Note:** Closures cannot be used inside PHP Attributes (`#[...]`). You must add them via `$validator->add()` or the
Form's `build()` method.

```php
// In Form::build()
$this->add('username', 'text', [
    'rules' => [
        'required',
        function ($value, $data) {
            if ($value === 'admin') {
                return 'The username "admin" is reserved.';
            }
            return true; // Return true if valid
        }
    ]
]);
```

---

## Custom Error Messages

You can customize error messages by using the Object Syntax for rules.

```php
#[StringField(rules: [
    new Required('Hey! We really need your email.'),
    new Email('This does not look like a valid email address.')
])]
protected string $email;
```

Alternatively, some string rules support message parameters depending on implementation, but Object syntax is preferred
for clarity and flexibility.