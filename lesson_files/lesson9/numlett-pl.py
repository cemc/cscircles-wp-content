x = int(input())
if x>=1 and x<=26:
    print('litera', x, 'w alfabecie to:', chr(ord('A')+(x-1)))
else:
    print('nieprawidłowe wprowadzenie:', x)

