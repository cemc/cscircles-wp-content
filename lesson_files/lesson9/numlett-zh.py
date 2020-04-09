x = int(input())
if x>=1 and x<=26:
    print('字母', x, '在字母表中:', chr(ord('A')+(x-1)))
else:
    print('无效输入:', x)

