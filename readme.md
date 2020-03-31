# Can't add errors using the Callback constraint.
Small reproducer that show a callback validator can't add a violation.

**Expected validation error: you cannot add a game if customer is not checked.**

Adding errors using `$field->addError(new FormError('Customer subscription required'));` is working but not using the Callback constraints.

A `Callback` constraint is added to the form using this:

```php
$form = $this->createFormBuilder($formData, [
    'constraints' => [
        new Callback([$this, 'validateCustomerSubscription']),
    ],
]);
```
But it is not possible to attach error to the field using this lines of code:

```php
$context
    ->buildViolation('Customer subscription required')
    ->atPath("children[games].children[$customerId].children[$gameId].data")
    ->addViolation();
```

The problem is due to the path `children[games].children[$customerId].children[$gameId].data` being modified to `data.children[games].children[$customerId].children[$gameId].data`.

I guess this path by having a look at the debug bar. Uncomment `new EqualTo(false)` to compare paths.

 ![](image.png)