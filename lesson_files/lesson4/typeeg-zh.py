inputStr = "12"                          # 用户的一个输入
print("The input type is", type(inputStr))
x = int(inputStr)
print(x, "is of type", type(x), "and its square is", x*x)
print(inputStr * inputStr)               # 这行代码会造成一个错误
