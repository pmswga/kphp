---
sort: 1
---

# Compiling KPHP from sources

KPHP can be compiled for Debian systems. Public builds are available for Buster and Ubuntu.

We have not tested if KPHP can be compiled for a non-Debian system. If you have luck, please let us know.


## Where all sources are located

All KPHP sources are [on GitHub]({{site.url_github_kphp}}), distributed under the GPLv3 license.  
These are sources of KPHP, vkext, flex data (for Russian declensions), and all TL tools.

KPHP linkage depends on some custom packages, that are also compiled from source:
* patched curl build [on GitHub]({{site.url_package_curl}}) (branch *dpkg-build-7.60.0*)
* custom uber-h3 build [on GitHub]({{site.url_package_h3}}) (branch *dpkg-build*)
* epoll implementation for MacOS [on GitHub]({{site.url_package_epoll_shim}}) (branch *osx-platform*)
* custom timelib build [on GitHub]({{site.url_package_timelib}}) (branch *master*)

KPHP is compiled with CMake and packed with CPack.


## Prerequisites


##### Debian 10 (Buster)
Add external repositories 
```bash
apt-get update
# utils for adding repositories
apt-get install -y --no-install-recommends apt-utils ca-certificates gnupg wget lsb-release
# for newest cmake package
echo "deb https://deb.debian.org/debian buster-backports main" >> /etc/apt/sources.list
# for curl-kphp-vk, libuber-h3-dev packages and kphp-timelib
wget -qO - https://repo.vkpartner.ru/GPG-KEY.pub | apt-key add -
echo "deb https://repo.vkpartner.ru/kphp-buster/ buster main" >> /etc/apt/sources.list 
# for php7.4-dev package
wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add -
echo "deb https://packages.sury.org/php/ buster main" >> /etc/apt/sources.list.d/php.list 
# for libmysqlclient-dev
TEMP_DEB="$(mktemp)"
wget -O "$TEMP_DEB" 'https://dev.mysql.com/get/mysql-apt-config_0.8.20-1_all.deb'
DEBIAN_FRONTEND=noninteractive dpkg -i "$TEMP_DEB"
rm -f "$TEMP_DEB"
```
Install packages
```bash
apt-get update
apt install git cmake-data=3.16* cmake=3.16* make g++ gperf python3-minimal python3-jsonschema \
            curl-kphp-vk libuber-h3-dev kphp-timelib libfmt-dev libgtest-dev libgmock-dev libre2-dev libpcre3-dev \
            libzstd-dev libyaml-cpp-dev libnghttp2-dev zlib1g-dev php7.4-dev libmysqlclient-dev libnuma-dev
```


##### Ubuntu 20.04 (Focal Fossa)
Add external repositories
```bash
apt-get update
# utils for adding repositories
apt-get install -y --no-install-recommends apt-utils ca-certificates gnupg wget
# for curl-kphp-vk, libuber-h3-dev packages and kphp-timelib
wget -qO - https://repo.vkpartner.ru/GPG-KEY.pub | apt-key add -
echo "deb https://repo.vkpartner.ru/kphp-focal/ focal main" >> /etc/apt/sources.list
```
Install packages
```bash
apt-get update
apt install git cmake make g++ gperf python3-minimal python3-jsonschema \
            curl-kphp-vk libuber-h3-dev kphp-timelib libfmt-dev libgtest-dev libgmock-dev libre2-dev libpcre3-dev \
            libzstd-dev libyaml-cpp-dev libnghttp2-dev zlib1g-dev php7.4-dev libmysqlclient-dev libnuma-dev
```


##### MacOS with Intel chipset
Make sure you have `brew` and `clang` (at least `Apple clang version 10.0.0`)
```bash
brew install re2c cmake coreutils glib-openssl libiconv re2 yaml-cpp mysql msgpack-cxx zstd googletest php@7.4
brew link --overwrite php@7.4
pip3 install jsonschema
```
Clone somewhere local [epoll-shim from GitHub]({{site.url_package_epoll_shim}}) and switch to *osx-platform* branch.
Add env variable `EPOLL_SHIM_REPO` to your bash profile. It allows to avoid of cloning `epoll-shim` on each clean cmake call.
```bash
git clone https://github.com/VKCOM/epoll-shim.git
cd epoll-shim
git checkout osx-platform
echo 'export "EPOLL_SHIM_REPO=$(pwd)" >> ~/.bash_profile'
```

Clone somewhere local [h3 from GitHub]({{site.url_package_h3_mac}}) and switch to *stable-3.x* branch.
```bash
git clone https://github.com/uber/h3.git
git checkout stable-3.x
mkdir build
cd build
cmake ..
sudo make install
```

##### MacOS with Apple M1 chipset

```note
Probably, there is an easier way to do this, but I couldn't find it, at least for late 2021.
```

Follow the steps above. Later on, you'll have to patch the [libucontext](https://github.com/kaniini/libucontext) library locally.
```bash
git clone https://github.com/kaniini/libucontext
cd libucontext
nano Makefile
```
Find assignment to `LIBUCONTEXT_LINKER_FLAGS` and replace it with:
```text
LIBUCONTEXT_LINKER_FLAGS = -dynamiclib -install_name ${LIBUCONTEXT_SONAME} -current_version ${LIBUCONTEXT_SOVERSION} -compatibility_version ${LIBUCONTEXT_SOVERSION}
```
Now we are ready to make libucontext:
```
make FREESTANDING=yes ARCH=aarch64
```
Finally, copy resulting files to the default brew search folder `/opt/homebrew`:
```
cp libucontext.a /opt/homebrew/lib
cp libucontext.dylib /opt/homebrew/lib
cp -r include/* /opt/homebrew/include/
```


##### Other Linux
Make sure you are using the same package list. You may use system default libcurl package, it would work, but without DNS resolving. `uber-h3` must be installed from sources.


### Recommendations
It's also recommended having a `libfmt >= 7` installed (if not, pass `-DDOWNLOAD_MISSING_LIBRARIES=On` to CMake) and `ccache`, it may speed up compilation time.  


## CMake configuration

Here are available variables, that can be modified during the `cmake` invocation:
```
DOWNLOAD_MISSING_LIBRARIES when libfmt / gtest / epoll-shim / etc not found, downloads them to a local folder [Off]
ADDRESS_SANITIZER enables the address sanitizer [Off]
UNDEFINED_SANITIZER enables the undefined sanitizer [Off]
KPHP_TESTS include tests to default target [On]
```


## Compiling KPHP

```note
We use the `make` command here, but of course, parallel compilation means `make -j$(nproc)` or env settings.
```

```bash
mkdir build
cd build
cmake ..
make
```


## Compiling tl2php and other tools

This is achieved with the same command:
```bash 
# in build/ folder
make
```


## Compiling vkext

To build and install vkext locally, you need to have `php` and `php-dev` packages installed for PHP 7.2 or above.  
vkext is also compiled with CMake.

```
# in build/ folder
make
```

**Install for the default PHP version (recommended)**

```
sudo cp ../objs/vkext/modules/vkext.so $(php-config --extension-dir)
```

If you want to use it everywhere, update the global *php.ini* file by adding the following lines:
```
extension=vkext.so
tl.conffile="/path/to/scheme.tlo"
```

If you don't want to install it globally, pass it as the `-d` option to *php* instead of updating *php.ini*:
```bash
php -d extension=vkext.so -d tl.conffile=/path/to/scheme.tlo ...
```  

**Build for a specific PHP version (optional)**

Run the following script with the desirable *PHP_VERSION* (only "7.2" and "7.4" are supported so far):
```
# in build/ folder
make vkext${PHP_VERSION}
sudo cp ../objs/vkext/modules${PHP_VERSION}/vkext.so $(php-config${PHP_VERSION} --extension-dir)
```

**Compiling .tlo for php.ini**

Use the `tl-compiler` executable from `vk-tl-tools` package:
```bash
tl-compiler -e /path/to/output.tlo input1.tl input2.tl ...
```


## Building a .deb package

```bash
# in build/ folder
cpack -D CPACK_COMPONENTS_ALL="KPHP;FLEX" .
```
