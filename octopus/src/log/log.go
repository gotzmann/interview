package log

import (
	"fmt"
	"os"
)

var File *os.File = os.Stdout
var Out *os.File = os.Stdout

func Fatalf(format string, args ...interface{}) {
	Out.Write(formatArgs(format, args...))
	os.Exit(1)
}

func Infof(format string, args ...interface{}) {
	File.Write(formatArgs(format, args...))
}

func Debugf(format string, args ...interface{}) {
	Out.Write(formatArgs(format, args...))
}

func Errorf(format string, args ...interface{}) {
	Out.Write(formatArgs(format, args...))
}

func formatArgs(format string, args ...interface{}) []byte {
	str := "\n" + fmt.Sprintf(format, args...)
	return []byte(str)
}