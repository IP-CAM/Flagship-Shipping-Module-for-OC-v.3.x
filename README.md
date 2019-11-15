# flagship-for-opencart
## Installation
Navigate to your OpenCart `ROOT` directory
````
composer require flagshipcompany/flagship-api-sdk:^1.1
````
Move storage folder from `upload/system/storage` to OpenCart `ROOT`

If you had already moved storage folder to OpenCart `ROOT`, change vendor directory path in composer.json and run 
````
composer require flagshipcompany/flagship-api-sdk:^1.1
````

Alternatively, you can download flagship-api-sdk from [here](https://github.com/flagshipcompany/flagship-api-sdk) and unzip the contents to storage/vendor/flagshipcompany
(Note that this would require `composer install`)

To install the extension, navigate to Admin > Extensions > Installer

Upload flagship.ocmod.zip

Refresh modifictaions from the top right corner on Modifications page ( Admin > Extensions > Modifications )

Refresh cache from Dashboard > Settings (Gear icon in the top right corner)

Navigate to Admin > Extensions > Extensions > Shipping
Install FlagShip Shipping
![Install FlagShip](https://github.com/flagshipcompany/flagship-for-opencart/blob/master/screenshots/installFlagShip.jpg)

Edit FlagShip
![Edit FlagShip](https://github.com/flagshipcompany/flagship-for-opencart/blob/master/screenshots/editFlagShip.jpg)

Enter your FlagShip token, flat handling fee, percentage markup to be added to shipments.
Enable the extension and set a sort order.
If you use certain shipping boxes, you can add their dimensions here.

## Usage
Be sure to set details for your store.

`Admin > System > Settings > Store > Store(tab) > `

Your customers will now see FlagShip shipping rates when choosing a shipping method.


![Shipping Rates](https://github.com/flagshipcompany/flagship-for-opencart/blob/master/screenshots/shippingRates.jpg)

You can Ship all your orders with FlagShip

![Send To FlagShip](https://github.com/flagshipcompany/flagship-for-opencart/blob/master/screenshots/sendToFlagShip.jpg)
