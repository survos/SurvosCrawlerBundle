# CrawlerBundle

One of the most basic ways to test a website is to simply go to the starting page and click on every link to make sure there are no pages broken.

Then repeat the process, but logged in as different users (e.g. as an administrator).

That's what this bundle does.  Combine with code coverage, it's a fast and easy way to test.  

```bash
composer req survos/crawler-bundle
```

# Working example (without API Platform)

```bash
symfony new crawler-7 --webapp --version=next --php=8.2 && cd crawler-7
composer config extra.symfony.allow-contrib true
echo "DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db" > .env.local
composer update
composer require form validator security-csrf      
composer require orm-fixtures doctrine/doctrine-fixtures-bundle --dev
echo "firstName,string,16,yes," | sed "s/,/\n/g"  | bin/console make:entity Official
echo "lastName,string,32,no," | sed "s/,/\n/g"  | bin/console make:entity Official
echo "officialName,string,48,no," | sed "s/,/\n/g"  | bin/console make:entity Official
echo "birthday,date_immutable,yes," | sed "s/,/\n/g"  | bin/console make:entity Official
echo "gender,string,1,yes," | sed "s/,/\n/g"  | bin/console make:entity Official
bin/console doctrine:schema:update --force --complete

# was bin/console make:crud Official -q
echo ",," | sed "s/,/\n/g"  | bin/console make:crud Official
sed -i "s|'app_app'|'app_homepage'|" src/Controller/OfficialController.php

bin/console make:controller AppController
sed -i "s|Route('/app'|Route('/'|" src/Controller/AppController.php
sed -i "s|'app_app'|'app_homepage'|" src/Controller/AppController.php
cat > templates/app/index.html.twig <<END
{% extends 'base.html.twig' %}
{% block body %}
    <h1>A simple CRUD</h1>
    <a href="{{ path('app_official_index') }}">Listing</a>
{% endblock %}
END

cat > src/DataFixtures/AppFixtures.php <<'END'
<?php

namespace App\DataFixtures;

use App\Entity\Official;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $url = 'https://theunitedstates.io/congress-legislators/legislators-current.json';
        $json = file_get_contents($url);
        foreach (json_decode($json) as $record) {
            $name = $record->name;
            $bio = $record->bio;
            $official = (new Official())
                ->setBirthday(new \DateTimeImmutable($bio->birthday))
                ->setGender($bio->gender)
                ->setFirstName($name->first)
                ->setLastName($name->last)
                ->setOfficialName($name->official_full ?? "$name->first $name->last");
            $manager->persist($official);
        }
    $manager->flush();
    }
}
END

bin/console d:fixtures:load -n

composer require stenope/stenope
bin/console -e prod cache:clear
bin/console -e prod stenope:build ./public/static/ --base-url=/static

```

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
