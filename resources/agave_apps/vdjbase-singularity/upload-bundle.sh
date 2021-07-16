#
TOOL=changeo-singularity
SYSTEM=ls5
VER=irplus-0.1

# delete old working area in tapis
tapis files delete agave:///irplus/apps/$TOOL/$VER/$SYSTEM

# create directory structure
tapis files mkdir agave:///irplus/apps $TOOL
tapis files mkdir agave:///irplus/apps/$TOOL $VER
tapis files mkdir agave:///irplus/apps/$TOOL/$VER $SYSTEM
tapis files mkdir agave:///irplus/apps/$TOOL/$VER/$SYSTEM test

# upload app assets
tapis files upload agave:///irplus/apps/$TOOL/$VER/$SYSTEM changeo.sh
tapis files upload agave:///irplus/apps/$TOOL/$VER/$SYSTEM changeo.json
tapis files upload agave:///irplus/apps/$TOOL/$VER/$SYSTEM ../common/changeo_common.sh
tapis files list agave:///irplus/apps/$TOOL/$VER/$SYSTEM

# upload test assets
tapis files upload agave:///irplus/apps/$TOOL/$VER/$SYSTEM/test test/test.sh
tapis files list agave:///irplus/apps/$TOOL/$VER/$SYSTEM/test
