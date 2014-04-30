About
===========

DMA Friends is an open source membership program based on [wordpress](http://wordpress.org) and 
[BadgeOS](http://badgeos.org/).  With friends any museum can provide an incentive program with
Badges and Rewards to enhance the visitor experience.

### Installation

1. Download the latest version of friends via git
```
git clone https://github.com/DallasMuseumArt/DMA-Friends.git
cd DMA-Friends
git submodule update
```

2. Change directory to themes
```
cd wp-content/themes
```

3. Download a copy of the base themes
```
git clone https://github.com/DallasMuseumArt/dma-friends-kiosk.git dma
git clone https://github.com/DallasMuseumArt/dma-friends-portal.git dma-portal
```

4. Follow the steps to do a basic [wordpress install](http://codex.wordpress.org/Installing_WordPress)

5. Select "Settings" > "Permalinks" from the left hand menu. Then select the option "Post name" and save your changes

6. Select "Plugins" from the left hand menu and activate the following plugins
    * BadgeOS
    * BadgeOS DMA Print
    * BadgeOS Rewards
    * DMA Custom Login Authentication for BadgeOS
    * DMA Platform
    * DMA SMS Functions
    * DMA Theme Switcher

7. Visit "Settings" > "Friends Themeswitcher".  Unless you have changed the name of the themes set "Kiosk Theme" to "DMA" and "Portal Theme" to "DMA Portal"

### Getting Started

Visit the [Getting Started](https://github.com/DallasMuseumArt/DMA-Friends/wiki/Getting-Started) wiki page for instructions on configuring Friends.

### iPad App
You can download the iPad app [here](https://github.com/DallasMuseumArt/DMA-Friends-iOS).  Information about building and deploying iOS apps is available at the [Apple developer portal](https://developer.apple.com/library/mac/documentation/IDEs/Conceptual/AppDistributionGuide/TestingYouriOSApp/TestingYouriOSApp.html)

### Contributions

Contributions created by partnering museums will be linked here
