
equivset.txt equivset.php equivset.ser: equivset.in
	php generateEquivset.php
	cp equivset.ser ../equivset.ser
	cp equivset.php ../equivset.php

equivset.in: equivset.head equivset_1 equivset_2 equivset_3
	cat equivset.head > equivset.in
	grep -v -h "^</*pre>" equivset_1 equivset_2 equivset_3 >> equivset.in
	echo "Regenerated $@. Remember to run 'git diff equivset.in' before commiting"

equivset_%:
	wget --user-agent="Extension AntiSpoof equivset.in rebuild" -O $@ "https://www.mediawiki.org/w/index.php?action=raw&title=Extension:AntiSpoof/Equivalence_sets/$@"

clean:
	rm -f equivset.in equivset_1 equivset_2 equivset_3 equivset.txt equivset.php equivset.ser
