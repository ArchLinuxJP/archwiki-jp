# OpenGraph
## 概要

OGPとTwitterカード(Summary Card)を追加する.


## インストール
この extensionは [Extension:PageImages \- MediaWiki](https://www.mediawiki.org/wiki/Extension:PageImages)、[Extension:TextExtracts \- MediaWiki](https://www.mediawiki.org/wiki/Extension:TextExtracts)に依存しています

(This extension has a hard dependency on Extension:TextExtracts and Extension:PageImages. )

LocalSettings.php
```php
 wfLoadExtension( 'OpenGraph' );
```

## 設定

```$ogpTwitter``` Twitterアカウントを指定できる (twitter:siteに入ります)

```php
 $ogpTwitter = '@48pedia' //option
```
