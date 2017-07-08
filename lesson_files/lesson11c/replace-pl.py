def xReplace(value):
    global x
    x = value

x = "zewnętrzny"
xReplace("wewnętrzny") 
print(x)       # 'wewnętrzny' teraz!
