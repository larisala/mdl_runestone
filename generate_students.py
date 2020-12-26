"""
Para usar esse script, rode o comando:

  $ python3 generate_students.py <students.csv> <coursename>

Onde <students.csv> é um arquivo csv com os seguintes campos:
  username email firstname lastname

e <coursename> é o nome identificador do curso Runestone.

Esse script adapta o arquivo .csv para criar os alunos no Runestone,
gerando uma senha alfanumérica aleatória de 8 caracteres para cada aluno.

Para que os alunos possam acessar sua conta, é necessário que a senha seja
informada de forma privada a cada um.

O arquivo é gerado no mesmo diretório em que o script está localizado.

"""

import sys
import random
import string
import pandas as pd

def make_csv(filename, coursename):
  df = pd.read_csv(filename)
  df_new = pd.DataFrame(columns=['username', 'email', 'first_name', 'last_name'],
    data=df[['username','email','firstname','lastname']].values)
  pw_length = 8
  df_new['password'] = get_random_pw(pw_length, len(df_new.index))
  df_new['course'] = coursename
  df_new.to_csv('students.csv', index=False)

def get_random_pw(pw_length, quantity):
  result_arr = []
  letters_and_digits = string.ascii_letters + string.digits
  for i in range (quantity):
    result = ''.join((random.choice(letters_and_digits) for i in range(pw_length)))
    result_arr.append(result)
  return result_arr

def main():
  if len(sys.argv) != 3:
    print('Uso: $ python3 generate_students.py <students.csv> <coursename>')
    exit()
  else:
    make_csv(sys.argv[1], sys.argv[2])

main()

