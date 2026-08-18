# Jogo da Cobrinha em PHP com Relatórios PDF

## Requisitos
- PHP 8.1 ou superior
- Extensão PDO SQLite habilitada
- Composer

## Instalação

1. Coloque a pasta no `htdocs` do XAMPP.
2. Abra o terminal dentro da pasta do projeto.
3. Execute:

```bash
composer install
```

4. Inicie o Apache no XAMPP.
5. Acesse:

`http://localhost/jogo_cobrinha_php/`

## Funcionamento

- O jogo roda no navegador com JavaScript.
- Ao perder, a pontuação e duração são enviadas para `save_game.php`.
- As partidas são armazenadas em `database.sqlite`.
- Em **Relatórios das partidas**, é possível visualizar o histórico.
- O botão **Gerar PDF** cria e baixa um relatório PDF usando Dompdf.

## Observação

A pasta `vendor` é criada automaticamente pelo Composer e não precisa ser enviada no projeto inicial.
