def hypotenuse(a, b):
    from math import sqrt
    try:
        res = sqrt(a*a+b*b)
    except TypeError:
        res = None
    if res == None:
        raise(TypeError("hypotenuse can only be applied to numbers;\n was given " + str(type(a)) + " and " + str(type(b))))
    return res
    
