# Google Tag Manager

This module is made to use the Google Tag Manager / Google Analytics 4.

## Installation

### Composer

```
composer require thelia/google-tag-manager-module:~2.1.0
```

## Usage

You need to configure the id from your Google tag manager account in the thelia administration panel.\
It should look like ```GTM-XXXX```. \
This will generate both the head script and the body no-script tags and insert them in the ```main.head-top```,
in the ```main.body-top```, in the ```main.javascript-initialization``` and in the ```product.bottom``` hooks. \
If these hooks are not present in your template, you'll need to add them beforehand.

To track products added to the cart, you need to implement this js event on the "Add to cart" buttons.
```js 
const event = new CustomEvent("addPseToCart", {
    detail: {
          pse: pseId,
          quantity
    },
 });
 document.dispatchEvent(event);
```
