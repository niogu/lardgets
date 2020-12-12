```html
{{ new \App\Http\Lardgets\Counter(0, \App\User::first()) }}

@lardgetsjs

public function input()
{
    return $this->sweetalert2->swalInputText('What?', 'input2', []);
}

public function input2($what)
{
    return $this->sweetalert2->swalText('You have entered: ' . $what);
}

```
