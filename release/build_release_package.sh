#!/bin/sh

ZIP_FILE_NAME=shopgate-shopware-integration.zip

rm -rf src/Backend/SgateShopgatePlugin/vendor release/package $ZIP_FILE_NAME
mkdir release/package
composer install -vvv --no-dev
rsync -av --exclude-from './release/exclude-filelist.txt' ./src/ release/package
rsync -av ./README.md release/package/Backend/SgateShopgatePlugin/
rsync -av ./LICENSE.md release/package/Backend/SgateShopgatePlugin/
rsync -av ./CONTRIBUTING.md release/package/Backend/SgateShopgatePlugin/
rsync -av ./CHANGELOG.md release/package/Backend/SgateShopgatePlugin/
cd release/package
zip -r ../../$ZIP_FILE_NAME .
cd ..
rm -rf package
