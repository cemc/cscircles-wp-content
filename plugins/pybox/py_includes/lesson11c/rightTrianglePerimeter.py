def rightTrianglePerimeter(a, b):
    try:
        res = a+b+hypotenuse(a, b)
    except TypeError:
        res = None
    if res == None:
        raise(TypeError("rightTrianglePerimeter can only be applied to numbers;\n was given " + str(type(a)) + " and " + str(type(b))))
    return res
    
