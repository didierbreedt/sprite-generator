Nfq Sprite Generator Bundle
============================
Generate sprite images and stylesheets with plain PHP and GD2 (no 3rd party dependencies)


## Installation
### Dependancies

PHP extension GD2


### Get the bundle

Add this in your composer.json

```json
{
	"require": {
		"nfq-alpha/sprite-bundle": "dev-master@dev"
	}
}
```

and then run

```sh
php composer.phar update
```

### Add the classes to your Kernel
```php
new SpriteGenerator\SpriteGeneratorBundle(),
```

### Configuration
You have to configure your sprites by adding lines to ```config.yml```:

```yaml
sprite_generator:
    sprites:
        spritename:
            inDir: %kernel.root_dir%/../src/Resources/public/img/sprites/
            outImage: %kernel.root_dir%/../src/Resources/public/img/sprite2.png
            outCss: %kernel.root_dir%/../src/Resources/public/scss/_sprites2.scss
            relativeImagePath: ../bundles/img/
            padding: 5
            spriteClass: sprite
            cssFormat: sass
            imagePositioning: one-column
            imageGenerator: gd2
        another_spritename:
            inDir: %kernel.root_dir%/../src/Resources/public/img/sprites/
            outImage: %kernel.root_dir%/../src/Resources/public/img/sprite2.png
            outCss: %kernel.root_dir%/../src/Resources/public/scss/_sprites2.scss
            relativeImagePath: ../bundles/img/
            padding: 5
            spriteClass: sprite_another
            cssFormat: sass
            imagePositioning: one-column
            imageGenerator: gd2
```

## Generate sprites
Generate all your sprites : 
```sh
$ php app/console nfq:sprite:generate
```

Generate one sprite : 
```sh
$ php app/console nfq:sprite:generate spritename
```
