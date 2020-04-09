def xReplace(value):
    global x
    x = value

x = "outer"
xReplace("inner")
print(x)          # Dieses Mal wird 'inner' ausgegeben!
