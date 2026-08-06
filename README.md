# melis-asset-manager

MelisAssetManager provides deliveries of Melis Platform's assets located in every module's public folder.

## Getting Started

These instructions will get you a copy of the project up and running on your machine.

### Prerequisites

MelisAssetManager will attempt to create a file in the main config folder of the project.  
It is important that the folder has enough rights to be written in.  
Filename and location:  
/config/melis.modules.path.php  


### Installing

Run the composer command:
```
composer require melisplatform/melis-asset-manager
```

## Running the code

The code will run by itself on loading of the modules by the use of a listenner.  
Assets are then accessible by using the following URLs:  
/[moduleName]/css/mycss.css  
/[moduleName]/js/myjs.js   
/[moduleName]/images/img.jpg  
  
MelisAssetManager will also attempt to find the requested elements in the main public folder.  

## Authors

* **Melis Technology** - [www.melistechnology.com](https://www.melistechnology.com/)

See also the list of [contributors](https://github.com/melisplatform/melis-asset-manager/contributors) who participated in this project.


## License

This project is licensed under the OSL-3.0 License - see the [LICENSE.md](LICENSE.md) file for details