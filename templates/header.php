<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Forecast System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <style>
        .sidebar {
            height: 100vh;
            background-color: #133fa2;
            color: white;
            position: fixed;
            width: 200px;
            padding-top: 15px;
            display: flex;
            flex-direction: column;
        }

        .sidebar a {
            padding: 8px 12px;
            text-decoration: none;
            font-size: 0.9rem;
            color: white;
            display: block;
        }

        .sidebar a:hover {
            background-color: #ee5b2f;
            color:  #ffffff;
        }

        .sidebar img {
            max-width: 100px; /* Redução da logo em 20% */
        }

        .content {
            margin-left: 210px; /* Ajuste para refletir a nova largura da sidebar */
            padding: 15px; /* Redução do espaçamento */
            font-size: 0.8rem; /* Reduzindo tamanho da fonte */
        }


        p, label, input, select, button {
            font-size: 0.9rem; /* Reduzindo tamanho do texto dos elementos */
        }

        .table {
            font-size: 0.9rem; /* Reduzindo tamanho do texto das tabelas */
        }

        .btn {
            font-size: 0.85rem; /* Reduzindo o tamanho dos botões */
            padding: 8px 16px; /* Ajustando espaçamento interno */
        }

        .card {
            padding: 15px; /* Ajuste de espaçamento interno */
        }


        .logout-btn {
            margin-bottom: 15px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
