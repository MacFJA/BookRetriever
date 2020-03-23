SHELL := /bin/bash

.PHONY: install all analyze analyze-tests docs clean composer

install: | vendor

all: analyze analyze-tests docs

analyze: | vendor vendor-bin/analyze/vendor
	$(COMPOSER) normalize
	$(COMPOSER) exec -v parallel-lint -- lib
	$(COMPOSER) exec -v php-cs-fixer -- fix
	$(COMPOSER) exec -v phpcpd -- --fuzzy --min-lines=4 --min-tokens=25 --progress --names-exclude=SearchResult.php lib
	$(COMPOSER) exec -v phpmd -- lib text phpmd.xml
	$(COMPOSER) exec -v phpa -- lib
	$(COMPOSER) exec -v phan -- --allow-polyfill-parser --directory lib --unused-variable-detection --dead-code-detection --target-php-version 7.2
	$(COMPOSER) exec -v phpstan -- analyse lib
	$(COMPOSER) exec -v psalm -- lib
	$(COMPOSER) exec -v phpunit

analyze-tests: | vendor vendor-bin/analyze/vendor
	$(COMPOSER) exec -v parallel-lint -- tests
	$(COMPOSER) exec -v php-cs-fixer -- fix --config=tests/.php_cs
	$(COMPOSER) exec -v phpcpd -- --fuzzy --min-lines=8 --min-tokens=50 --progress tests
	$(COMPOSER) exec -v phpmd -- tests text phpmd.xml
	$(COMPOSER) exec -v phpa -- tests
	$(COMPOSER) exec -v phan -- --config-file .phan/config-tests.php --allow-polyfill-parser --directory tests --unused-variable-detection --dead-code-detection --target-php-version 7.2
	$(COMPOSER) exec -v phpstan -- analyse tests
	$(COMPOSER) exec -v psalm -- tests

docs: | vendor vendor-bin/docs/vendor
	$(COMPOSER) exec -v phploc -- --log-xml=build/phploc.xml lib
	$(COMPOSER) exec -v phpdox

clean::
	@echo Remove all generated files
	rm -f composer.phar
	rm -f .php_cs.cache
	rm -f .phpunit.result.cache
	rm -f composer.lock
	rm -f vendor-bin/qa/composer.lock
	rm -f vendor-bin/docs/composer.lock
	@echo Remove all generated directories
	rm -rf vendor
	rm -rf vendor-bin/qa/vendor
	rm -rf vendor-bin/docs/vendor
	rm -rf build
	rm -rf docs
	# # Or
	# grep -v -E "^(#.+)?$" .gitignore vendor-bin/.gitignore |\
	# 	sed 's/.gitignore://' | sed 's#^#./#' | sed 's#//#/#' |\
	# 	xargs -I % rm -rf %
	# # Or
	# find . -name .gitignore | xargs -I % sh -c  "sed '/^$/d' % | sed '/^#/d' | sed 's#^#%#'" |\
	# 	sed 's#/.gitignore#/#' | sed 's#//#/#' | sed '/!/d' |\
	#	xargs -I % rm -rf %

# Files

vendor: composer.json
	$(COMPOSER) install --optimize-autoloader

vendor-bin/analyze/vendor: | vendor
	$(COMPOSER) bin analyze install --optimize-autoloader --prefer-dist

vendor-bin/docs/vendor: | vendor
	$(COMPOSER) bin docs install --optimize-autoloader --prefer-dist

composer.phar:
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	php composer-setup.php --quiet
	rm composer-setup.php

# Check Composer installation
ifneq ($(shell command -v composer > /dev/null ; echo $$?), 0)
  ifneq ($(MAKECMDGOALS),composer.phar)
    $(shell $(MAKE) composer.phar)
  endif
  COMPOSER=php composer.phar
else
  COMPOSER=composer
endif

# Magic "make composer ..." command
ifeq ($(firstword $(MAKECMDGOALS)),composer)
  COMPOSER_ARGS=$(wordlist 2, $(words $(MAKECMDGOALS)), $(MAKECMDGOALS))
  $(eval $(COMPOSER_ARGS):;@:)
endif
composer:
	$(COMPOSER) $(COMPOSER_ARGS)