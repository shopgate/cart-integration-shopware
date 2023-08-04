### Preparation

The tests will require the Shopware `SwagDemoDataEN` (or DE) plugin to be installed.

We've added some sample data via SQL statements as the Shopware API does
not allow to add certain data. Copy the .sql files from `tests/assets` folder
to the installation folder of the `SwagDemoData` plugin:
```shell
cp assets/*.sql [root]/engine/Shopware/Plugins/Community/Frontend/SwagDemoDataEN/resources/data/
cd [root]
bin/console sw:plugin:reinstall -r SwagDemoDataEN
```

### Postman tests

* Go to `SW admin -> Config -> User Admin -> Edit -> enable API`
* Copy the API key
* Install Shopgate Plugin
* Configure Shopgate plugin in Shopware admin:
  * `sg_customer_number` -> `12345`
  * `sg_shop_number` -> `123456`
  * `sg_api_key` -> `1111111111111111111`

#### Run via Postman

* Import postman files into Postman (you will find them in the Postman folder)
* Paste API key into the imported postman environment, field called `sw_api_key`
* Right click on collection folder & click to `Run collection`

----
##### Run via NPM CLI
Follow the instructions above to get the API key from SW5, then substitute in query below. 
You may or may not need the port number, depends on your isntance URL.

```shell
npm i
node_modules/.bin/newman run ./Postman/collection.json -e ./Postman/environment.json -g ./Postman/globals.json --global-var 'system_port=:8000' --env-var 'sw_api_key=XXX' --color=on --bail
```
