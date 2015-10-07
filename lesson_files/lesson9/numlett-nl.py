x = int(input())
if x>=1 and x<=26:
    print('letter', x, 'in het alfabet:', chr(ord('A')+(x-1)))
else:
    print('ongeldige input:', x)

