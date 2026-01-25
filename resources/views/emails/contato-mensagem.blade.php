Nova mensagem do formulario de contato

Nome: {{ data_get($data, 'nome') }}
E-mail: {{ data_get($data, 'email') }}
Telefone: {{ data_get($data, 'telefone', 'Nao informado') }}
Assunto: {{ data_get($data, 'assunto') }}

Mensagem:
{{ data_get($data, 'mensagem') }}
