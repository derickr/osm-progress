#!/usr/bin/python
#
# Returns coordinates that would include the bbox depending on image size

import mapnik
import sys, os, pprint

if __name__ == "__main__":
    ll = [];
    for arg in sys.argv:
        ll.append(arg)
    ll.pop(0);
    for index, arg in enumerate(ll):
        ll[index] = float(arg)

    imgx = 1280
    imgy = 720

    m = mapnik.Map(imgx,imgy)
    prj = mapnik.Projection("+proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +no_defs +over")
    c0 = prj.forward(mapnik.Coord(ll[0],ll[1]))
    c1 = prj.forward(mapnik.Coord(ll[2],ll[3]))
    bbox = mapnik.Envelope(c0.x,c0.y,c1.x,c1.y)
    m.zoom_to_box(bbox)

    e = m.envelope();
    c0 = prj.inverse(mapnik.Coord(e.minx, e.miny))
    c1 = prj.inverse(mapnik.Coord(e.maxx, e.maxy))

    print ("%s %s %s %s" % (c0.x, c0.y, c1.x, c1.y))
