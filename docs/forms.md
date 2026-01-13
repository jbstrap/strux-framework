# Forms

The Strux Framework provides a robust, object-oriented system for building forms and validating data. This system
abstracts the complexity of HTML rendering, data binding, and validation logic into reusable classes.

---

## Table of Contents

- Creating a Form
- Defining Fields
- Rendering Forms
- Handling Submissions
- Model Binding

---

## Creating a Form

To create a form, extend the `Strux\Component\Form\Form` class. You can define fields using PHP 8 Attributes (
recommended) or the `build()` method.

### Example: `src/Http/Form/RegistrationForm.php`

```php
namespace App\Http\Form;

use Strux\Component\Form\Form;
use Strux\Component\Form\Attributes\StringField;
use Strux\Component\Form\Attributes\PasswordField;
use Strux\Component\Form\Attributes\SelectField;
use Strux\Component\Form\Attributes\ButtonField;

class RegistrationForm extends Form
{
    // Attributes define the field type, label, rules, and HTML attributes.
    // Using 'protected' properties is recommended to encapsulate state.
    
    #[StringField(label: 'Full Name', rules: ['required', 'minlength:3'])]
    protected string $name;

    #[StringField(label: 'Email Address', rules: ['required', 'email'])]
    protected string $email;

    #[PasswordField(label: 'Password', rules: ['required', 'minlength:8'])]
    protected string $password;

    #[SelectField(
        label: 'Role', 
        options: ['user' => 'User', 'admin' => 'Admin'],
        rules: ['required']
    )]
    protected string $role = 'user'; // Default value

    #[ButtonField(label: 'Register', attributes: ['class' => 'btn btn-primary'])]
    protected string $submit;
}
````

---

## Defining Fields

You can define fields in two ways:

### 1. PHP Attributes (Recommended)

This method keeps your form definition declarative and close to the property.

| Attribute     | Description         | Example                                 |
|---------------|---------------------|-----------------------------------------|
| StringField   | Standard text input | `#[StringField(label: 'Name')]`         |
| PasswordField | Password input      | `#[PasswordField]`                      |
| SelectField   | Dropdown menu       | `#[SelectField(options: ['a' => 'A'])]` |
| ButtonField   | Submit button       | `#[ButtonField(label: 'Save')]`         |

---

### 2. The `build()` Method

Use this for dynamic logic or complex rules that attributes don't support (like Closures).

```php
public function build(): void
{
    $this->add('bio', 'textarea', [
        'label' => 'Biography',
        'rules' => ['required'],
        'attributes' => ['rows' => 5]
    ]);
}
```

---

## Rendering Forms

In your view templates, you can access the form fields to render labels, inputs, and errors.

### Using Helpers (`field()`)

Recommended when properties are protected.

```php
<!-- views/auth/register.php -->

<form method="POST">
    <div class="mb-3">
        <?= $form->field('name')->label(['class' => 'form-label']) ?>
        <?= $form->field('name')->input(['class' => 'form-control']) ?>
        <?= $form->field('name')->error('text-danger') ?>
    </div>

    <!-- Render other fields... -->

    <?= $form->field('submit')->input() ?>
</form>
```

---

### Using Magic Properties

Works if properties are protected (via `__get`) or public.

```php
<?= $form->email->label() ?>
<?= $form->email->input() ?>
```

---

## Handling Submissions

In your controller, you can instantiate the form with the current Request. This automatically binds the data.

```php
use Strux\Component\Http\Response;
use App\Http\Form\RegistrationForm;

public function register(Request $request): Response
{
    // 1. Instantiate (Auto-binds request data)
    $form = new RegistrationForm($request);

    // 2. Validate (Only runs if request is bound)
    if ($request->getMethod() === 'POST' && $form->isValid()) {
        
        // 3. Get clean data
        $data = $form->getData(); 
        // OR access properties: $name = $form->getName(); (if getters exist)

        // Save logic...
        return new Response(200, [], "User {$data['name']} created!");
    }

    // 4. Render View (Form retains input values & errors on failure)
    return $this->view('auth/register', ['form' => $form]);
}
```

---

## Model Binding

You can populate a form with data from a Database Model, an Array, or an Entity. This is useful for "Edit" forms.

```php
public function edit(int $id): Response
{
    $user = User::find($id); // Returns User object
    
    // Automatically matches User properties to Form fields
    $form = new RegistrationForm($user);

    return $this->view('auth/edit', ['form' => $form]);
}
```