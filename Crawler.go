package main

import (
	"fmt"
	/*"io/ioutil"
	"net/http"*/
	"github.com/gocolly/colly"
)

/*func main() {
	resp, err := http.Get("http://google.com")

	fmt.Println("http transport error is:", err)

	body, err := ioutil.ReadAll(resp.Body)

	fmt.Println("read error is:", err)

	fmt.Println(string(body))
}
*/

func main() {
	c := colly.NewCollector()

	// Find and visit all links
	c.OnHTML("a[href]", func(e *colly.HTMLElement) {
		e.Request.Visit(e.Attr("href"))
	})

	c.OnRequest(func(r *colly.Request) {
		fmt.Println("Visiting", r.URL)
	})

	c.Visit("https://google.com/")
}
