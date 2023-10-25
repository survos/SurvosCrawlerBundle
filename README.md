# CrawlerBundle

One of the most basic ways to test a website is to simply go to the starting page and click on every link to make sure there are no pages broken.

Then repeat the process, but logged in as different users (e.g. as an administrator).

That's what this bundle does.  Combine with code coverage, it's a fast and easy way to test.  

```bash
composer req survos/crawler-bundle
```

symfony new crawl-demo --webapp --version=7.0 --php=8.2 && cd crawl-demo
composer config extra.symfony.allow-contrib true
bin/console make:controller Bug -i
composer req survos/crawler-bundle

Start the server.  Until proxy is working (@todo) you need to use the IP address of the server if you're using the Symfony CLI.

To set default values (@todo: install recipe)
```yaml
# config/packages/survos_crawler.yaml
survos_crawler:
  base_url: 'https://127.0.0.1:8000'
```

## The process

```bash
bin/console survos:crawl
```

Note that the first time this runs, it will create a VisitLinksTest.php in the tests directory, so that phpunit works.  

The command visits every link and stores the results in crawldata.json. This is then used by the tests to make sure they're right.

This is particularly good when different users have permissions to different routes, so if you've secured an /admin route and accidentally left the link open, you'll get an error.





```bash

symfony new --demo crawler_bundle_demo


```
