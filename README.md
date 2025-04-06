# meap

## 環境立ち上げ
### フロントエンド
```
cd client
npm run dev
npx local-ssl-proxy --key ..\certificates\localhost-key.pem --cert ..\certificates\localhost.pem  --source 3000 --target 3001 
```

### バックエンド
```
cd server
./vendor/bin/sail up -d
```
