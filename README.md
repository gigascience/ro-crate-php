# ro-crate-php

This is a PHP tool to create and manipulate Research Object Crate.

## Install

Install the tool using composer:
>composer require alex/my-php-app

## Docs
The phpDoc is under preparation.

## Usage

Create a new empty crate with the base path set to resources directory in the parent directory:

> $crate = new  ROCrate(\_\_DIR\_\_  .  '/../resources', false);
	
The `ROCrate` constructor enables the creation of a crate using an existing metadata file:

> $crate = new ROCrate(\_\_DIR\_\_ . '/../resources', true);
	
Add an entity to the crate:
> // A person
> <br> $author = $crate->createGenericEntity('#alice', ['Person']);
> <br> $author->addProperty('name', 'Alice Smith');
> <br> $author->addProperty('affiliation', 'Institution of Example');
> <br> // Add the person to the crate
> <br> \$crate->addEntity($author);
>
> // Adds the person as one of the creators of the root data entity, i.e. the dataset being described by the crate
> <br> $root = $crate->getRootDataset();
> <br> $root->addPropertyPair('creator', '#alice', true);

Interact with the crate just like normal objects with methods:
> \$crate->addEntity($author);
> <br> \$crate->removeEntity($author->getId());

Chain up the methods to enhance the compactness of the code when adding/removing properties of an entity:
> $root->addPropertyPair('creator', '#bob')
> <br> &ensp; ->addPropertyPair('creator', '#cathy')
> <br> &ensp; ->removePropertyPair('creator', '#alice')
> <br> &ensp; ->addPropertyPair('creator', '#alice');


## Change Log
The tool is currently under development.