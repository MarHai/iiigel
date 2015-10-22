
import cgi, os, sys

def error(msg):
	print "[ERROR]: " + msg
	sys.exit()

if (len(sys.argv) <= 1):
	error("At least one parameter is required")

input = os.path.abspath(sys.argv[1])

if (not os.path.isfile(input)):
	error("Can't find input-file: \"" + input + "\"")

if (len(sys.argv) > 2):
	output = os.path.abspath(sys.argv[2])
else:
	output = input + ".html"

f = open(input, "r")
i_content = f.read()
f.close()

lines = i_content.split('\n')
o_content = "<!DOCTYPE html>\n<html><head><title>" + os.path.basename(input) + "</title></head><body><p>\n"

def escape(line):
	return cgi.escape(line).encode('ascii', 'xmlcharrefreplace').replace('\t', '    ').replace(' ', '&nbsp;')

for line in lines:
	o_content += "<a style='background-color: #00FF00;'>" + escape(line) + "</a><br>\n"

o_content += "</body></html>"

f = open(output, "w")
f.write(o_content)
f.close()

## COMMENT!!! hihihi
