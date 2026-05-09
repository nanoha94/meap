#!/bin/bash

# コンテナを起動
./vendor/bin/sail up -d

# コンテナが完全に起動するまで少し待機
sleep 5
echo "--------------------------------------------------------------------------------------"
echo "Swagger UI  :  https://localhost:8000/api/documentation"
echo "Laravel     :  https://localhost:8000/"
echo "PgAdmin     :  https://localhost:8080/"
echo "Mailpit     :  http://localhost:8025/"
echo "--------------------------------------------------------------------------------------"
