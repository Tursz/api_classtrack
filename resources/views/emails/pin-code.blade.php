<x-mail::message>
# Olá, {{ $userName }}!

Use o código abaixo para confirmar sua conta no Classtrack:

<x-mail::panel>
# {{ $pinCode }}
</x-mail::panel>

Esse código expira em **15 minutos**.

Se você não solicitou este cadastro, pode ignorar este e-mail.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>