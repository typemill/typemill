# -*- coding: utf-8 -*-
# Converts dictionaries in language files to the same form
# Author of this script: Paul bid (https://paul.bid)


def f_open(lc):
	with open('{}.yaml'.format(lc), 'r', encoding='utf-8') as file_open:
		try:
			file_data = file_open.read()
		except FileNotFoundError:
			print('ERROR: File {}.yaml not found in this directory (in the same place as the script).'.format(lc))
	return file_data


def f_save(lc, new):
	with open('{}.yaml'.format(lc), 'w', encoding='utf-8') as file_save:
		file_save.write(new)
	return


if __name__ == '__main__':
	lang_codes = ['en', 'de', 'fr', 'it', 'nl', 'ru']  # add new lang codes here if you need it
	data = f_open(lang_codes[0]).split('\n')[2:]
	key = [t.split(': ', 1)[0] for t in data]
	for i in lang_codes[1:]:  # all lang codes except en, because en used like template
		any_header = f_open(i).split('\n')
		any_data = any_header[2:]
		any_header = any_header[:2]
		any_key = [t.split(': ', 1)[0] for t in any_data]
		new_file, result = [], ''
		for u in range(0, len(key)):
			if key[u] in any_key:  # key exist
				new_file.append(any_data[any_key.index(key[u])])
			else:  # key does not exist
				new_file.append(data[u])
		any_header.extend(new_file)
		result = '\n'.join(any_header)
		f_save(i, result)
	input('Was checked {} lang files.\nTotal strings for translation - {}.\nJob Done, press Enter to exit!'
		  .format(len(lang_codes[1:]), len(data)))
