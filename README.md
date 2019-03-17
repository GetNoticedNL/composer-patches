# Get.Noticed - Composer Patches plugin

A Composer plugin that allows you to apply patch files for certain packages. You can also specify which certain versions
of a package should be patched and you could also make them rely on specific versions of other packages.

## Support notes

* PHP: 7.1 or later
* PHP extension JSON

## Installation

1. Require the `getnoticed/composer-patches` package in your project.
2. In your composer.json, add the following code:
```json
"extra": {
  "patching-enabled": true,
  "patching-patches-file": "<path-to-patches-file>"
}
```

### Keep in mind
The `patching-patches-file` should contain a path to a file where you keep your patches.
You may place this file at whatever location you prefer, even a Composer package for easy maintaining, however please
do note that this file must be present at the time of the Composer command running, otherwise the patches will not be detected.

### Patches JSON file
In [dist/patches.json.dist](dist/patches.json.dist) you will find an example patches file.

* `name`: This field is a small description (<50 characters) of your patch.
* `description`: here you may use a larger text to more accurately describe your patch.
* `target`: this is the package you will be patching.
* `conditions`: you may specify conditions for when the patch should be applied here.
  * `target`: may be either `_self` (to use the main patch target) or any valid package name.
  * `constraint`: the installed version of the package must match this version to attempt to apply the patch
  * `optional`: if the `optional` flag is set to true and the package is not present/installed/required, this condition will be skipped.
* `filepath`: Specify the path to the patch file
* `precision`: Specify the `-p<N>` level that should be used by `git apply`

# Why this Composer patches plugin and not one of the others?

Well, the reason why we created this Composer patches plugin is because the other ones out on the market didn't really suit our needs.

* Either we ran into severe bugs or problems with their integration
* They were too complex (not easy to setup or use)
* Or too simple (didn't support all functionality we require)
