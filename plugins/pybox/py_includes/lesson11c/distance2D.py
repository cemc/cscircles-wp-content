def distance2D(x1, y1, x2, y2):
    try:
        return hypotenuse(x1-x2, y1-y2)
    except TypeError:
        res = None
    if res == None:
        raise(TypeError("distance2D can only be applied to numbers;\n was given " + str(type(x1)) + " " + str(type(y1)) + " " + str(type(x2)) + " " + str(type(y2))))
    return res
    
