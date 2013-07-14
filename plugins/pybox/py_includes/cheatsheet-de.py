3 * 4, 3 + 4, 3 - 4, 3 / 4                #==> 12, 7, -1, 0.75
3 ** 4, 3 // 4, 3 % 4                     #==> 81, 0, 3
4 > 3, 4 >= 3, 3 == 3.0, 3 != 4, 3 <= 4   #==> True, True, True, True, True
# Ausführungsreihenfolge: Klammern, **, {* / // %}, {+ -}, {== != <= < > >=}
min(3, 4), max(3, 4), abs(-10)            #==> 3, 4, 10
sum([1, 2, 3])  # [1, 2, 3] is a list     #==> 6

type(3), type(3.0), type("myVariable")    #==> &lt;class 'int'&gt;, &lt;class 'float'&gt;,
                                          #    &lt;class 'str'&gt;
int("4"+"0"), float(3), str(1 / 2)        #==> 40, 3.0, '0.5'

"double quotes: ', escaped \" \\ \'"      #==> double quotes: ', escaped " \ '
'it\'s "similar" in single quotes '       #==> it's "similar" in single quotes

ord("A"), chr(66)                         #==> 65, 'B'
string = "hello"
# das folgende gilt auch für Listen
len(string)                               #==> 5
string[0], string[4]    # Zeichen ermitteln==> "h", "o"
string[1:3]             # Teilzeichenkette#==> "el"
string[:2], string[2:]  # l/r Teilkette   #==> "he", "llo"
string[-1], string[-2:] # negativer Index #==> "o", "lo"
"ver" + "knüpf" + "ung " + str(123)     #==> "verknüpfung 123"
"boo" * 2                                 #==> "booboo"

getLineOfInputAsString = input()          #==> Eingabe lesen (oder EOF Fehler)
print("akzeptiert", 0, "oder mehr")       #==> akzeptiert 0 oder mehr
print("eigenen", "sep", "nutzen", sep=".")#==> eigenen.sep.nutzen
print("kein", "zeilenumbruch", end="end") #==> kein zeilenumbruchend

not True, False or True, False and True   #==> False, True, False
# Ausführungsreihenfolge: Klammern, {== !=}, not, and, or

if booleanCondition:
   x                      # inneren Teil einrücken
   x                      # jede Zeile mit der gleichen Einrückung gehört dazu
elif anotherCondition:    # es kann einen oder mehrere "elif"s geben
   x                      # Mehrzeiliger Block
else:                     # sonst (optional)
   x                      # Mehrzeiliger Block

while booleanCondition:
   x                      # die eigentlichen Anweisungen
   break                  # Schleife abbrechen (optional)
   continue               # Schleife von oben neustarten (optional)

for indexVariable in range(low, highPlus):
   print(indexVariable)                   #==> low, low+1, ..., highPlus-1
# "for item in listOrString:" forall/foreach Schleife
# break und continue können in allen Schleifen genutzt werden

def nameOfNewFunction(argument1, argument2):
    x                     # die eigentlichen Anweisungen
    return y              # (optional; wenn kein return genutzt wird erfolgt automatisch return Null)

def remember(bar):        # globale Variable benutzen
    global saveBar        # nach dem Aufruf von foo(3) ist saveBar = 3
    saveBar = bar         # auch außerhalb der Funktion

# 'slice'-Befehle können auch mit Listen und range()s genutzt werden
"0123456789"[::2]         # slices        #==> "02468"
"0123456789"[::-1]        # rückwärts     #==> "9876543210"
"0123456789"[6:3:-1]                      #==> "654"

x += 1        # auch: -=, /=, *=, %=, **=, //=. Python hat kein "x++"!
x, y = y, x   # mehrere Anweisungen
3 < x < 5     # entspricht "(3 < x) and (x < 5)". möglich mit {< <= > >= == != is}

import math               # import, alle Funktionen durch Punkt aufrufen
print(math.sqrt(2))
from math import sqrt     # import, eine Funktion ohne Punkt aufrufen
print(sqrt(2))
# auch in math: pi, exp, log, sin, cos, tan, ceil, floor und mehr

list = ['zero', 'one', 'two']
list.index('one')                         #==> 1
list.index('three')                       #==> Fehler
'three' in index, 'zero' in index         #==> False, True
list.count('two')                         #==> 1
del list[1]                               # list = ['zero', 'two']
"string" in "superstring"                 #==> True
"superstring".index("string")             #==> 5

# weitere Listenmethoden: append(item), insert(item, index), extend(list),
# remove(value), pop(), pop(index), reverse(), sort() und mehr

# einige Zeichenkettenmethoden: capitalize(), lower/upper(), islower/isupper(),
# isalpha/isdigit(), center/ljust/rjust(width, fillChar), strip(), split(),
# splitlines(), endwith/startwith(string), find(string), replace(old, new)
# und mehr

myList = [11, 99]
actuallyTheSameList = myList # keine Vollständige Kopie, nur eine Kopie der Referenz
myList is actuallyTheSameList             #==> True
realCopy = myList[:]         # oder list(myList), copy.copy(myList), deepcopy
realCopy is myList                        #==> False
