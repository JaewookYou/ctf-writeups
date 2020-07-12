`slick-logger` `web` `5 solvers`



```go
func searchHandler(w http.ResponseWriter, r *http.Request) {
	channelID, ok := r.URL.Query()["channel"]
	if !ok || !validateParameter(channelID[0]) {
		http.Error(w, "channel parameter is required", 400)
		return
	}

	queries, ok := r.URL.Query()["q"]
	if !ok || !validateParameter(queries[0]) {
		http.Error(w, "q parameter is required", 400)
		return
	}

	users := []User{}
	readJSON("/var/lib/data/users.json", &users)

	dir, _ := strconv.Unquote(channelID[0])
	query, _ := strconv.Unquote(queries[0])

	if strings.HasPrefix(dir, "G") {
		http.Error(w, "You cannot view private channels, sorry!", 403)
		return
	}

	re, _ := regexp.Compile("(?i)" + query)

	messages := []Message{}
	filepath.Walk("/var/lib/data/", func(path string, _ os.FileInfo, _ error) error {
		if strings.HasPrefix(path, "/var/lib/data/"+dir) && strings.HasSuffix(path, ".json") {
			newMessages := []Message{}
			readJSON(path, &newMessages)
			for _, message := range newMessages {
				if re.MatchString(message.Text) {
					// Fill in user info
					for _, user := range users {
						if user.ID == message.UserID {
							messages = append(messages, Message{
								Text:      re.ReplaceAllString(message.Text, "<em>$0</em>"),
								UserID:    message.UserID,
								UserName:  user.Name,
								Icon:      user.Profile.Image,
								Timestamp: message.Timestamp,
							})
							break
						}
					}
				}
			}
		}
		return nil
	})

	result, _ := json.Marshal(messages)

	w.WriteHeader(http.StatusOK)
	header := w.Header()
	header.Set("Content-type", "application/json")
	fmt.Fprint(w, string(result))
}
```

in search function, they get input from us, `channel` and `q`



but they use specific parameter form like `"value"`, and unquote it.

so if we give `"""`, it is going to blank, we could search like `like '%'` in mysql db.

(thanks for @Payload at GON)



from now, 

```go
filepath.Walk("/var/lib/data/", func(path string, _ os.FileInfo, _ error) error {
	if strings.HasPrefix(path, "/var/lib/data/"+dir) && strings.HasSuffix(path, ".json") {
		newMessages := []Message{}
		readJSON(path, &newMessages)
```

we could get secret channel's admin message,



```go
users := []User{}
readJSON("/var/lib/data/users.json", &users)

...

for _, user := range users {
	if user.ID == message.UserID {
```

but because of this condition, there is no userid `USLACKBOT` at `users.json`



```go
	for _, message := range newMessages {
>>>		if re.MatchString(message.Text) {
			// Fill in user info
			for _, user := range users {
```



but our searching word, `query`, is going to argument of `regexp.Compile`, it is used before `userid` matching.



so we could give it blind regex injection. (https://portswigger.net/daily-swig/blind-regex-injection-theoretical-exploit-offers-new-way-to-force-web-apps-to-spill-secrets)



as searching words' length is growing, accuracy of blind regex injection decreases.

moderately slice our searching words to get flag!



and accuracy is little bit low, so we send same payload for several time, and get statistics of most delayed word! XD

```
[*] 5 : 0.06300497055053711
[*] } : 0.062014102935791016
[*] N : 0.05901360511779785
---------------------
[*] } : 0.06202411651611328
[*] 0 : 0.060013771057128906
[*] W : 0.058012962341308594
---------------------
[*] U : 0.09402132034301758
[*] B : 0.0660254955291748
[*] } : 0.06601500511169434
---------------------
[*] } : 0.06301426887512207
[*] Z : 0.06101393699645996
[*] Y : 0.060013532638549805
---------------------
[*] } : 0.06601476669311523
[*] H : 0.06502485275268555
[*] 6 : 0.05902361869812012
---------------------
[Finished in 13.1s]

## this could be `}` is the currect word
```





check my exploit

https://github.com/JaewookYou/ctf-writeups/blob/master/2020TSGCTF/slick-logger/ex.py


