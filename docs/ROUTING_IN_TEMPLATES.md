Смарти позволяет получить ссылки на роуты по именам двумя способами:

Напрямую:
```php

<a href="{Arris\AppRouter::getRouter('view.form.login')}">Вход</a>

```

И опосредованно:
```php
App::$template->assign("routing", AppRouter::getRoutersNames());
```

Но в этом случае сказать
```php
<a href="{$routing.view.form.login}">Вход</a>
```
нельзя!

Нужно:
```php
<a href="{$routing['view.form.login']}">Вход</a>
```

Какой вариант выглядит прозрачнее - вопрос! 