#
TOOL=changeo-singularity
SYSTEM=ls5
VER=irplus-0.1


# upload app assets
tapis files upload agave:///irplus/apps/$TOOL/$VER/$SYSTEM changeo.sh
tapis files upload agave:///irplus/apps/$TOOL/$VER/$SYSTEM changeo.json
tapis files upload agave:///irplus/apps/$TOOL/$VER/$SYSTEM ../common/changeo_common.sh
tapis files list agave:///irplus/apps/$TOOL/$VER/$SYSTEM

# upload test assets
tapis files upload agave:///irplus/apps/$TOOL/$VER/$SYSTEM/test test/test.sh
tapis files list agave:///irplus/apps/$TOOL/$VER/$SYSTEM/test
