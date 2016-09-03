def xReplace(value):
    global x
    x = value

x = "outer"
xReplace("inner") 
print(x)       # 现在是'inner'!
