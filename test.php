<?php


function get_pc_screen_size() {
    return explode('x', trim(`xdpyinfo | grep dimensions | perl -pe 's|.*?(\d*x\d*).*|\\1|'`));
}
var_dump(get_pc_screen_size());
